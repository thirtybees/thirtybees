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
 *  @author    thirty bees <contact@thirtybees.com>
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2017-2024 thirty bees
 *  @copyright 2007-2016 PrestaShop SA
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

/**
 * @property Gender|null $object
 */
class AdminGendersControllerCore extends AdminController
{
    /**
     * @throws PrestaShopException
     */
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

        $this->fieldImageSettings = [
            [
                'inputName' => 'image',
                'path' => _PS_GENDERS_DIR_
            ]
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

    /**
     * @return void
     * @throws PrestaShopException
     */
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

    /**
     * @return string|void
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
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
            'img_width' => 16,
            'img_height' => 16,
            'image' => $obj->getImage()
        ];

        return parent::renderForm();
    }

    /**
     * @param int $id
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function postImage($id)
    {
        if (isset($this->fieldImageSettings[0]['inputName']) && isset($this->fieldImageSettings[0]['path'])) {
            if (!Validate::isInt(Tools::getValue('img_width')) || !Validate::isInt(Tools::getValue('img_height'))) {
                $this->errors[] = Tools::displayError('Width and height must be numeric values.');
            } else {
                if (Tools::getIntValue('img_width') > 0 && Tools::getIntValue('img_height') > 0) {
                    $width = Tools::getIntValue('img_width');
                    $height = Tools::getIntValue('img_height');
                } else {
                    $width = null;
                    $height = null;
                }
                return $this->uploadImage($id, $this->fieldImageSettings[0]['inputName'], $this->fieldImageSettings[0]['path'].'/', false, $width, $height);
            }
        }
        return !count($this->errors);
    }
}
