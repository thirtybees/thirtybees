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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2018 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * @property Gender $object
 */
class AdminGendersControllerCore extends AdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'gender';
        $this->className = 'Gender';
        $this->lang = true;
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->context = Context::getContext();

        if (!Tools::getValue('realedit')) {
            $this->deleted = false;
        }

        $this->bulk_actions = [
            'delete' => [
                'text' => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon' => 'icon-trash'
            ]
        ];

        $this->default_image_height = 16;
        $this->default_image_width = 16;

        $this->fieldImageSettings = [
            'name' => 'image',
            'dir' => 'genders'
        ];

        $this->fields_list = [
            'id_gender' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'name' => [
                'title' => $this->l('Social title'),
                'filter_key' => 'b!name'
            ],
            'type' => [
                'title' => $this->l('Gender'),
                'orderby' => false,
                'type' => 'select',
                'list' => [
                    0 => $this->l('Male'),
                    1 => $this->l('Female'),
                    2 => $this->l('Neutral')
                ],
                'filter_key' => 'a!type',
                'callback' => 'displayGenderType',
                'callback_object' => $this
            ],
            'image' => [
                'title' => $this->l('Image'),
                'align' => 'center',
                'image' => 'genders',
                'orderby' => false,
                'search' => false
            ]
        ];

        parent::__construct();
    }

    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_gender'] = [
                'href' => static::$currentIndex.'&addgender&token='.$this->token,
                'desc' => $this->l('Add new title', null, null, false),
                'icon' => 'process-icon-new'
            ];
        }

        parent::initPageHeaderToolbar();
    }

    public function renderForm()
    {
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Social titles'),
                'icon' => 'icon-male'
            ],
            'input' => [
                [
                    'type' => 'text',
                    'label' => $this->l('Social title'),
                    'name' => 'name',
                    'lang' => true,
                    'col' => 4,
                    'hint' => $this->l('Invalid characters:').' 0-9!&lt;&gt;,;?=+()@#"�{}_$%:',
                    'required' => true
                ],
                [
                    'type' => 'radio',
                    'label' => $this->l('Gender'),
                    'name' => 'type',
                    'required' => false,
                    'class' => 't',
                    'values' => [
                        [
                            'id' => 'type_male',
                            'value' => 0,
                            'label' => $this->l('Male')
                        ],
                        [
                            'id' => 'type_female',
                            'value' => 1,
                            'label' => $this->l('Female')
                        ],
                        [
                            'id' => 'type_neutral',
                            'value' => 2,
                            'label' => $this->l('Neutral')
                        ]
                    ]
                ],
                [
                    'type' => 'file',
                    'label' => $this->l('Image'),
                    'name' => 'image',
                    'col' => 6,
                    'value' => true
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Image width'),
                    'name' => 'img_width',
                    'col' => 2,
                    'hint' => $this->l('Image width in pixels. Enter "0" to use the original size.')
                ],
                [
                    'type' => 'text',
                    'label' => $this->l('Image height'),
                    'name' => 'img_height',
                    'col' => 2,
                    'hint' => $this->l('Image height in pixels. Enter "0" to use the original size.')
                ]
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ]
        ];

        /** @var Gender $obj */
        if (!($obj = $this->loadObject(true))) {
            return;
        }

        $this->fields_value = [
            'img_width' => $this->default_image_width,
            'img_height' => $this->default_image_height,
            'image' => $obj->getImage()
        ];

        return parent::renderForm();
    }

    public function displayGenderType($value, $tr)
    {
        return $this->fields_list['type']['list'][$value];
    }

    protected function postImage($id)
    {
        if (isset($this->fieldImageSettings['name']) && isset($this->fieldImageSettings['dir'])) {
            if (!Validate::isInt(Tools::getValue('img_width')) || !Validate::isInt(Tools::getValue('img_height'))) {
                $this->errors[] = Tools::displayError('Width and height must be numeric values.');
            } else {
                if ((int)Tools::getValue('img_width') > 0 && (int)Tools::getValue('img_height') > 0) {
                    $width = (int)Tools::getValue('img_width');
                    $height = (int)Tools::getValue('img_height');
                } else {
                    $width = null;
                    $height = null;
                }
                return $this->uploadImage($id, $this->fieldImageSettings['name'], $this->fieldImageSettings['dir'].'/', false, $width, $height);
            }
        }
        return !count($this->errors) ? true : false;
    }

    protected function afterImageUpload()
    {
        parent::afterImageUpload();

        if (($id_gender = (int)Tools::getValue('id_gender')) &&
             isset($_FILES) && count($_FILES) && file_exists(_PS_GENDERS_DIR_.$id_gender.'.jpg')) {
            $current_file = _PS_TMP_IMG_DIR_.'gender_mini_'.$id_gender.'_'.$this->context->shop->id.'.jpg';

            if (file_exists($current_file)) {
                unlink($current_file);
            }
        }

        return true;
    }
}
