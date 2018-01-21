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
 * Class AdminScenesControllerCore
 *
 * @since 1.0.0
 */
class AdminScenesControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    /** @var bool $bootstrap */
    public $bootstrap = true;
    // @codingStandardsIgnoreEnd

    /**
     * AdminScenesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->table = 'scene';
        $this->className = 'Scene';
        $this->lang = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->identifier = 'id_scene';
        $this->fieldImageSettings = [
            ['name' => 'image', 'dir' => 'scenes'],
            ['name' => 'thumb', 'dir' => 'scenes/thumbs'],
        ];

        $this->fields_list = [
            'id_scene' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'     => [
                'title'      => $this->l('Image Maps'),
                'filter_key' => 'b!name',
            ],
            'active'   => [
                'title'   => $this->l('Activated'),
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
                'active'  => 'status',
                'type'    => 'bool',
                'orderby' => false,
            ],
        ];

        parent::__construct();
    }

    /**
     * Render form
     *
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        $this->initFieldsForm();

        /** @var Scene $obj */
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $this->tpl_form_vars['products'] = $obj->getProducts(true, $this->context->language->id, false, $this->context);

        return parent::renderForm();
    }

    /**
     * Init fields form
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initFieldsForm()
    {
        $obj = $this->loadObject(true);
        $sceneImageTypes = ImageType::getImagesTypes('scenes');
        $largeSceneImageType = null;
        $thumbSceneImageType = null;
        foreach ($sceneImageTypes as $sceneImageType) {
            if ($sceneImageType['name'] == 'scene_default') {
                $largeSceneImageType = $sceneImageType;
            }
            if ($sceneImageType['name'] == 'm_scene_default') {
                $thumbSceneImageType = $sceneImageType;
            }
        }
        $fieldsForm = [
            'legend'      => [
                'title' => $this->l('Image Maps'),
                'icon'  => 'icon-picture',
            ],
            'description' => '
				<h4>'.$this->l('How to map products in the image:').'</h4>
				<p>
					'.$this->l('When a customer hovers over the image, a pop-up appears displaying a brief description of the product.').'
					'.$this->l('The customer can then click to open the full product page.').'<br/>
					'.$this->l('To achieve this, please define the \'mapping zone\' that, when hovered over, will display the pop-up.').'
					'.$this->l('Left click with your mouse to draw the four-sided mapping zone, then release.').'<br/>
					'.$this->l('Then begin typing the name of the associated product, and  a list of products will appear.').'
					'.$this->l('Click the appropriate product and then click OK. Repeat these steps for each mapping zone you wish to create.').'<br/>
					'.$this->l('When you have finished mapping zones, click "Save Image Map."').'
				</p>',
            'input'       => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Image map name'),
                    'name'     => 'name',
                    'lang'     => true,
                    'required' => true,
                    'hint'     => $this->l('Invalid characters:').' <>;=#{}',
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
                    'name'     => 'active',
                    'required' => false,
                    'class'    => 't',
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
            ],
            'submit'      => [
                'title' => $this->l('Save'),
            ],
        ];
        $this->fields_form = $fieldsForm;

        $imageToMapDesc = '';
        $imageToMapDesc .= '<div class="help-block">'.$this->l('Format:').' JPG, GIF, PNG. '.$this->l('File size:').' '
            .(Tools::getMaxUploadSize() / 1024).''.$this->l('Kb max.').' '
            .sprintf(
                $this->l('If an image is too large, it will be reduced to %1$d x %2$dpx (width x height).'),
                $largeSceneImageType['width'], $largeSceneImageType['height']
            )
            .$this->l('If an image is deemed too small, a white background will be added in order to achieve the correct image size.').'<br />'.
            $this->l('Note: To change image dimensions, please change the \'large_scene\' image type settings to the desired size (in Back Office > Preferences > Images).')
            .'</div>';

        if ($obj->id && file_exists(_PS_SCENE_IMG_DIR_.$obj->id.'-scene_default.jpg')) {
            $this->addJqueryPlugin('autocomplete');
            $this->addJqueryPlugin('imgareaselect');
            $this->addJs(_PS_JS_DIR_.'admin/scenes.js');
            $imageToMapDesc .= '<div class="panel panel-default"><span class="thumbnail row-margin-bottom"><img id="large_scene_image" alt="" src="'.
                _THEME_SCENE_DIR_.$obj->id.'-scene_default.jpg?rand='.(int) rand().'" /></span>';

            $imageToMapDesc .= '
				<div id="ajax_choose_product" class="row" style="display:none;">
					<div class="col-lg-12">
					<p class="alert alert-info">'
                .$this->l('Begin typing the first few letters of the product name, then select the product you are looking for from the drop-down list:').'
					</p>
					<div class="input-group row-margin-bottom">
						<span class="input-group-addon">
							<i class="icon-search"></i>
						</span>
						<input type="text" value="" id="product_autocomplete_input" />
					</div>
					<button type="button" class="btn btn-default" onclick="undoEdit();"><i class="icon-remove"></i>&nbsp;'.$this->l('Delete').'</button>
					<button type="button" class="btn btn-default" onclick="$(this).prev().search();"><i class="icon-check-sign"></i>&nbsp;'.$this->l('Ok').'</button>
					</div>
				</div>
				';

            if ($obj->id && file_exists(_PS_SCENE_IMG_DIR_.'thumbs/'.$obj->id.'-m_scene_default.jpg')) {
                $imageToMapDesc .= '</div><hr/><img class="thumbnail" id="large_scene_image" style="clear:both;border:1px solid black;" alt="" src="'._THEME_SCENE_DIR_.'thumbs/'.$obj->id.'-m_scene_default.jpg?rand='.(int) rand().'" />';
            }

            $imgAltDesc = '';
            $imgAltDesc .= $this->l('If you want to use a thumbnail other than one generated from simply reducing the mapped image, please upload it here.')
                .'<br />'.$this->l('Format:').' JPG, GIF, PNG. '
                .$this->l('File size:').' '.(Tools::getMaxUploadSize() / 1024).''.$this->l('Kb max.').' '
                .sprintf(
                    $this->l('Automatically resized to %1$d x %2$dpx (width x height).'),
                    $thumbSceneImageType['width'], $thumbSceneImageType['height']
                ).'.<br />'
                .$this->l('Note: To change image dimensions, please change the \'m_scene_default\' image type settings to the desired size (in Back Office > Preferences > Images).');

            $inputImgAlt = [
                'type'  => 'file',
                'label' => $this->l('Alternative thumbnail'),
                'name'  => 'thumb',
                'desc'  => $imgAltDesc,
            ];

            $selectedCat = [];
            if (Tools::isSubmit('categories')) {
                foreach (Tools::getValue('categories') as $row) {
                    $selectedCat[] = $row;
                }
            } elseif ($obj->id) {
                foreach (Scene::getIndexedCategories($obj->id) as $row) {
                    $selectedCat[] = $row['id_category'];
                }
            }

            $this->fields_form['input'][] = [
                'type'  => 'categories',
                'label' => $this->l('Categories'),
                'name'  => 'categories',
                'tree'  => [
                    'id'                  => 'categories-tree',
                    'title'               => 'Categories',
                    'selected_categories' => $selectedCat,
                    'use_search'          => true,
                    'use_checkbox'        => true,
                ],
            ];
        } else {
            $imageToMapDesc .= '<span>'.$this->l('Please add a picture to continue mapping the image.').'</span>';
        }

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->fields_form['input'][] = [
            'type'          => 'file',
            'label'         => $this->l('Image to be mapped'),
            'name'          => 'image',
            'display_image' => true,
            'desc'          => $imageToMapDesc,
        ];

        if (isset($inputImgAlt)) {
            $this->fields_form['input'][] = $inputImgAlt;
        }
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
            $this->page_header_toolbar_btn['new_scene'] = [
                'href' => static::$currentIndex.'&addscene&token='.$this->token,
                'desc' => $this->l('Add new image map', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Initialize toolbar
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function initToolbar()
    {
        parent::initToolbar();

        if (in_array($this->display, ['add', 'edit'])) {
            $this->toolbar_btn = array_merge(
                [
                    'save-and-stay' => [
                        'short' => 'SaveAndStay',
                        'href'  => '#',
                        'desc'  => $this->l('Save and stay'),
                    ],
                ],
                $this->toolbar_btn
            );
        }
    }

    /**
     * Post processing
     *
     * @return bool
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (Tools::isSubmit('save_image_map')) {
            if (!Tools::isSubmit('categories') || !count(Tools::getValue('categories'))) {
                $this->errors[] = Tools::displayError('You should select at least one category.');
            }
            if (!Tools::isSubmit('zones') || !count(Tools::getValue('zones'))) {
                $this->errors[] = Tools::displayError('You should create at least one zone.');
            }
        }

        if (Tools::isSubmit('delete'.$this->table)) {
            if (Validate::isLoadedObject($object = $this->loadObject())) {
                $object->deleteImage(false);
            } else {
                return false;
            }
        }
        parent::postProcess();
    }

    /**
     * After image upload
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function afterImageUpload()
    {
        /* Generate image with differents size */
        if (!($obj = $this->loadObject(true))) {
            return false;
        }

        if ($obj->id && (isset($_FILES['image']) || isset($_FILES['thumb']))) {
            $baseImgPath = _PS_SCENE_IMG_DIR_.$obj->id.'.jpg';
            $imagesTypes = ImageType::getImagesTypes('scenes');

            foreach ($imagesTypes as $k => $imageType) {
                if ($imageType['name'] == 'm_scene_default') {
                    if (isset($_FILES['thumb']) && !$_FILES['thumb']['error']) {
                        $baseThumbPath = _PS_SCENE_THUMB_IMG_DIR_.$obj->id.'.jpg';
                    } else {
                        $baseThumbPath = $baseImgPath;
                    }
                    ImageManager::resize(
                        $baseThumbPath,
                        _PS_SCENE_THUMB_IMG_DIR_.$obj->id.'-'.stripslashes($imageType['name']).'.jpg',
                        (int) $imageType['width'],
                        (int) $imageType['height']
                    );
                    if (ImageManager::retinaSupport()) {
                        ImageManager::resize(
                            $baseThumbPath,
                            _PS_SCENE_THUMB_IMG_DIR_.$obj->id.'-'.stripslashes($imageType['name']).'2x.jpg',
                            (int) $imageType['width'] * 2,
                            (int) $imageType['height'] * 2
                        );
                    }
                    if (ImageManager::webpSupport()) {
                        ImageManager::resize(
                            $baseThumbPath,
                            _PS_SCENE_THUMB_IMG_DIR_.$obj->id.'-'.stripslashes($imageType['name']).'.webp',
                            (int) $imageType['width'],
                            (int) $imageType['height'],
                            'webp'
                        );
                        if (ImageManager::retinaSupport()) {
                            ImageManager::resize(
                                $baseThumbPath,
                                _PS_SCENE_THUMB_IMG_DIR_.$obj->id.'-'.stripslashes($imageType['name']).'2x.webp',
                                (int) $imageType['width'] * 2,
                                (int) $imageType['height'] * 2,
                                'webp'
                            );
                        }
                    }
                } elseif (isset($_FILES['image']) && isset($_FILES['image']['tmp_name']) && !$_FILES['image']['error']) {
                    ImageManager::resize(
                        $baseImgPath,
                        _PS_SCENE_IMG_DIR_.$obj->id.'-'.stripslashes($imageType['name']).'.jpg',
                        (int) $imageType['width'],
                        (int) $imageType['height']
                    );
                    if (ImageManager::retinaSupport()) {
                        ImageManager::resize(
                            $baseImgPath,
                            _PS_SCENE_IMG_DIR_.$obj->id.'-'.stripslashes($imageType['name']).'2x.jpg',
                            (int) $imageType['width'] * 2,
                            (int) $imageType['height'] * 2
                        );
                    }
                    if (ImageManager::webpSupport()) {
                        ImageManager::resize(
                            $baseImgPath,
                            _PS_SCENE_IMG_DIR_.$obj->id.'-'.stripslashes($imageType['name']).'.webp',
                            (int) $imageType['width'],
                            (int) $imageType['height'],
                            'webp'
                        );
                        if (ImageManager::retinaSupport()) {
                            ImageManager::resize(
                                $baseImgPath,
                                _PS_SCENE_IMG_DIR_.$obj->id.'-'.stripslashes($imageType['name']).'2x.webp',
                                (int) $imageType['width'] * 2,
                                (int) $imageType['height'] * 2,
                                'webp'
                            );
                        }
                    }
                }
            }

            if ((int) Configuration::get('TB_IMAGES_LAST_UPD_SCENES') < $obj->id) {
                Configuration::updateValue('TB_IMAGES_LAST_UPD_SCENES', $obj->id);
            }
        }

        return true;
    }
}
