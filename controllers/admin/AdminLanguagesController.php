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
 * Class AdminLanguagesControllerCore
 *
 * @since 1.0.0
 */
class AdminLanguagesControllerCore extends AdminController
{
    /**
     * AdminLanguagesControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'lang';
        $this->className = 'Language';
        $this->lang = false;
        $this->deleted = false;
        $this->multishop_context = Shop::CONTEXT_ALL;

        $this->context = Context::getContext();

        $this->fieldImageSettings = [
            [
                'name' => 'flag',
                'dir'  => 'l',
            ],
            [
                'name' => 'no_picture',
                'dir'  => 'p',
            ],
        ];

        $this->fields_list = [
            'id_lang'          => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'flag'             => [
                'title'   => $this->l('Flag'),
                'align'   => 'center',
                'image'   => 'l',
                'orderby' => false,
                'search'  => false,
                'class'   => 'fixed-width-xs',
            ],
            'name'             => [
                'title' => $this->l('Name'),
            ],
            'iso_code'         => [
                'title' => $this->l('ISO code'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'language_code'    => [
                'title' => $this->l('Language code'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'date_format_lite' => [
                'title' => $this->l('Date format'),
            ],
            'date_format_full' => [
                'title' => $this->l('Date format (full)'),
            ],
            'active'           => [
                'title'  => $this->l('Enabled'),
                'align'  => 'center',
                'active' => 'status',
                'type'   => 'bool',
                'class'  => 'fixed-width-sm',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'confirm' => $this->l('Delete selected items?'),
                'icon'    => 'icon-trash',
            ],
        ];
        $this->specificConfirmDelete = $this->l('When you delete a language, all related translations in the database will be deleted. Are you sure you want to proceed?');

        parent::__construct();
    }

    /**
     * Initialize page header toolbar
     *
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_language'] = [
                'href' => static::$currentIndex.'&addlang&token='.$this->token,
                'desc' => $this->l('Add new language', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Render list
     *
     * @return false|string
     *
     * @since 1.0.0
     */
    public function renderList()
    {
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->displayWarning($this->l('When you delete a language, all related translations in the database will be deleted.'));
        if (!is_writable(_PS_ROOT_DIR_.'/.htaccess') && Configuration::get('PS_REWRITING_SETTINGS')) {
            $this->displayInformation($this->l('Your .htaccess file must be writable.'));
        }

        return parent::renderList();
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
        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Languages'),
                'icon'  => 'icon-globe',
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'ps_version',
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Name'),
                    'name'      => 'name',
                    'maxlength' => 32,
                    'required'  => true,
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('ISO code'),
                    'name'      => 'iso_code',
                    'required'  => true,
                    'maxlength' => 2,
                    'hint'      => $this->l('Two-letter ISO code (e.g. FR, EN, DE).'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Language code'),
                    'name'      => 'language_code',
                    'required'  => true,
                    'maxlength' => 5,
                    'hint'      => $this->l('IETF language tag (e.g. en-US, pt-BR).'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Date format'),
                    'name'     => 'date_format_lite',
                    'required' => true,
                    'hint'     => sprintf($this->l('Short date format (e.g., %s).'), 'Y-m-d'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Date format (full)'),
                    'name'     => 'date_format_full',
                    'required' => true,
                    'hint'     => sprintf($this->l('Full date format (e.g., %s).'), 'Y-m-d H:i:s'),
                ],
                [
                    'type'     => 'file',
                    'label'    => $this->l('Flag'),
                    'name'     => 'flag',
                    'required' => false,
                    'hint'     => $this->l('Upload the country flag from your computer.'),
                ],
                [
                    'type'     => 'file',
                    'label'    => $this->l('"No-picture" image'),
                    'name'     => 'no_picture',
                    'hint'     => $this->l('Image is displayed when "no picture is found".'),
                    'required' => false,
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Is RTL language'),
                    'name'     => 'is_rtl',
                    'required' => false,
                    'is_bool'  => true,
                    'values'   => [
                        [
                            'id'    => 'is_rtl_on',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                    'hint'     => $this->l('Enable if this language is read from right to left.').' '.$this->l('(Experimental: your theme must be compliant with RTL languages).'),
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Status'),
                    'name'     => 'active',
                    'required' => false,
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
                    'hint'     => $this->l('Activate this language.'),
                ],
            ],
        ];

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        $this->fields_form['submit'] = [
            'title' => $this->l('Save'),
        ];

        /** @var Language $obj */
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        if ($obj->id && !$obj->checkFiles()) {
            $this->fields_form['new'] = [
                'legend'     => [
                    'title' => $this->l('Warning'),
                    'image' => '../img/admin/warning.gif',
                ],
                'list_files' => [
                    [
                        'label' => $this->l('Translation files'),
                        'files' => Language::getFilesList($obj->iso_code, _THEME_NAME_, false, false, 'tr', true),
                    ],
                    [
                        'label' => $this->l('Theme files'),
                        'files' => Language::getFilesList($obj->iso_code, _THEME_NAME_, false, false, 'theme', true),
                    ],
                    [
                        'label' => $this->l('Mail files'),
                        'files' => Language::getFilesList($obj->iso_code, _THEME_NAME_, false, false, 'mail', true),
                    ],
                ],
            ];
        }

        $this->fields_value = ['ps_version' => _PS_VERSION_];

        return parent::renderForm();
    }

    /**
     * Process delete
     *
     * @return bool|false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processDelete()
    {
        $object = $this->loadObject();
        if (!$this->checkDeletion($object)) {
            return false;
        }
        if (!$this->deleteNoPictureImages((int) $object->id)) {
            $this->errors[] = Tools::displayError('An error occurred while deleting the object.').' <b>'.$this->table.'</b> ';
        }

        return parent::processDelete();
    }

    /**
     * Check deletion
     *
     * @param $object
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function checkDeletion($object)
    {
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }

        if (Validate::isLoadedObject($object)) {
            if ($object->id == Configuration::get('PS_LANG_DEFAULT')) {
                $this->errors[] = $this->l('You cannot delete the default language.');
            } elseif ($object->id == $this->context->language->id) {
                $this->errors[] = $this->l('You cannot delete the language currently in use. Please select a different language.');
            } else {
                return true;
            }
        } else {
            $this->errors[] = Tools::displayError('(cannot load object)');
        }

        return false;
    }

    /**
     * deleteNoPictureImages will delete all default image created for the language id_language
     *
     * @param string $idLanguage
     *
     * @return bool true if no error
     *
     * @since 1.0.0
     */
    protected function deleteNoPictureImages($idLanguage)
    {
        $language = Language::getIsoById($idLanguage);
        $imageTypes = ImageType::getImagesTypes('products');
        $dirs = [_PS_PROD_IMG_DIR_, _PS_CAT_IMG_DIR_, _PS_MANU_IMG_DIR_, _PS_SUPP_IMG_DIR_, _PS_MANU_IMG_DIR_];
        foreach ($dirs as $dir) {
            foreach ($imageTypes as $k => $imageType) {
                if (file_exists($dir.$language.'-default-'.stripslashes($imageType['name']).'.jpg')) {
                    if (!unlink($dir.$language.'-default-'.stripslashes($imageType['name']).'.jpg')) {
                        $this->errors[] = Tools::displayError('An error occurred during image deletion process.');
                    }
                }
            }

            if (file_exists($dir.$language.'.jpg')) {
                if (!unlink($dir.$language.'.jpg')) {
                    $this->errors[] = Tools::displayError('An error occurred during image deletion process.');
                }
            }
        }

        return !count($this->errors) ? true : false;
    }

    /**
     * Process status
     *
     * @return bool|false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processStatus()
    {
        $object = $this->loadObject();
        if ($this->checkDisableStatus($object)) {
            $this->checkEmployeeIdLang($object->id);

            return parent::processStatus();
        }

        return false;
    }

    /**
     * Check disable status
     *
     * @param $object
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function checkDisableStatus($object)
    {
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }
        if (!Validate::isLoadedObject($object)) {
            $this->errors[] = Tools::displayError('An error occurred while updating the status for an object.').' <b>'.$this->table.'</b> '.Tools::displayError('(cannot load object)');
        } else {
            if ($object->id == (int) Configuration::get('PS_LANG_DEFAULT')) {
                $this->errors[] = Tools::displayError('You cannot change the status of the default language.');
            } else {
                return true;
            }
        }

        return false;
    }

    /**
     * Check employee language id
     *
     * @param $currentIdLang
     *
     * @since 1.0.0
     */
    protected function checkEmployeeIdLang($currentIdLang)
    {
        //update employee lang if current id lang is disabled
        Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'employee` set `id_lang`='.(int) Configuration::get('PS_LANG_DEFAULT').' WHERE `id_lang`='.(int) $currentIdLang);
    }

    /**
     * Process add
     *
     * @return false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processAdd()
    {
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }

        if (isset($_POST['iso_code']) && !empty($_POST['iso_code']) && Validate::isLanguageIsoCode(Tools::getValue('iso_code')) && Language::getIdByIso($_POST['iso_code'])) {
            $this->errors[] = Tools::displayError('This ISO code is already linked to another language.');
        }
        if ((!empty($_FILES['no_picture']['tmp_name']) || !empty($_FILES['flag']['tmp_name'])) && Validate::isLanguageIsoCode(Tools::getValue('iso_code'))) {
            if ($_FILES['no_picture']['error'] == UPLOAD_ERR_OK) {
                $this->copyNoPictureImage(strtolower(Tools::getValue('iso_code')));
            }
            unset($_FILES['no_picture']);
        }

        $success = parent::processAdd();

        if (empty($_FILES['flag']['tmp_name'])) {
            Language::_copyNoneFlag($this->object->id, $_POST['iso_code']);
        }

        return $success;
    }

    /**
     * Copy a no-product image
     *
     * @param string $language Language iso_code for no_picture image filename
     *
     * @return void
     */
    public function copyNoPictureImage($language)
    {
        if (isset($_FILES['no_picture']) && $_FILES['no_picture']['error'] === 0) {
            if ($error = ImageManager::validateUpload($_FILES['no_picture'], Tools::getMaxUploadSize())) {
                $this->errors[] = $error;
            } else {
                if (!($tmpName = tempnam(_PS_TMP_IMG_DIR_, 'PS')) || !move_uploaded_file($_FILES['no_picture']['tmp_name'], $tmpName)) {
                    return;
                }
                if (!ImageManager::resize($tmpName, _PS_IMG_DIR_.'p/'.$language.'.jpg')) {
                    $this->errors[] = Tools::displayError('An error occurred while copying "No Picture" image to your product folder.');
                }
                if (!ImageManager::resize($tmpName, _PS_IMG_DIR_.'c/'.$language.'.jpg')) {
                    $this->errors[] = Tools::displayError('An error occurred while copying "No picture" image to your category folder.');
                }
                if (!ImageManager::resize($tmpName, _PS_IMG_DIR_.'m/'.$language.'.jpg')) {
                    $this->errors[] = Tools::displayError('An error occurred while copying "No picture" image to your manufacturer folder.');
                } else {
                    $imageTypes = ImageType::getImagesTypes('products');
                    foreach ($imageTypes as $k => $imageType) {
                        if (!ImageManager::resize($tmpName, _PS_IMG_DIR_.'p/'.$language.'-default-'.stripslashes($imageType['name']).'.jpg', $imageType['width'], $imageType['height'])) {
                            $this->errors[] = Tools::displayError('An error occurred while resizing "No picture" image to your product directory.');
                        }
                        if (!ImageManager::resize($tmpName, _PS_IMG_DIR_.'c/'.$language.'-default-'.stripslashes($imageType['name']).'.jpg', $imageType['width'], $imageType['height'])) {
                            $this->errors[] = Tools::displayError('An error occurred while resizing "No picture" image to your category directory.');
                        }
                        if (!ImageManager::resize($tmpName, _PS_IMG_DIR_.'m/'.$language.'-default-'.stripslashes($imageType['name']).'.jpg', $imageType['width'], $imageType['height'])) {
                            $this->errors[] = Tools::displayError('An error occurred while resizing "No picture" image to your manufacturer directory.');
                        }
                    }
                }
                unlink($tmpName);
            }
        }
    }

    /**
     * Process update
     *
     * @return bool|false|ObjectModel
     *
     * @since 1.0.0
     */
    public function processUpdate()
    {
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return false;
        }

        if ((isset($_FILES['no_picture']) && !$_FILES['no_picture']['error'] || isset($_FILES['flag']) && !$_FILES['flag']['error'])
            && Validate::isLanguageIsoCode(Tools::getValue('iso_code'))
        ) {
            if ($_FILES['no_picture']['error'] == UPLOAD_ERR_OK) {
                $this->copyNoPictureImage(strtolower(Tools::getValue('iso_code')));
            }
            // class AdminTab deal with every $_FILES content, don't do that for no_picture
            unset($_FILES['no_picture']);
        }

        /** @var Language $object */
        $object = $this->loadObject();
        if (Tools::getValue('active') != (int) $object->active) {
            if (!$this->checkDisableStatus($object)) {
                return false;
            }
        }

        $this->checkEmployeeIdLang($object->id);

        return parent::processUpdate();
    }

    /**
     * Ajax process check language pack
     *
     * @return void
     *
     * @since 1.0.0
     */
    public function ajaxProcessCheckLangPack()
    {
        $this->errors[] = $this->l('Our apologies. Language packs aren\'t in the first few thirty bees releases.');
    }

    /**
     * Process bulk delete
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function processBulkDelete()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $idLang) {
                $object = new Language((int) $idLang);
                if (!$this->checkDeletion($object)) {
                    return false;
                }
                if (!$this->deleteNoPictureImages((int) $object->id)) {
                    $this->errors[] = Tools::displayError('An error occurred while deleting the object.').' <b>'.$this->table.'</b> ';

                    return false;
                }
            }
        }

        return parent::processBulkDelete();
    }

    /**
     * Process bulk disable selection
     *
     * @return bool
     *
     * @since 1.0.0
     */
    protected function processBulkDisableSelection()
    {
        if (is_array($this->boxes) && !empty($this->boxes)) {
            foreach ($this->boxes as $idLang) {
                $object = new Language((int) $idLang);
                if (!$this->checkDisableStatus($object)) {
                    return false;
                }
                $this->checkEmployeeIdLang($object->id);
            }
        }

        return parent::processBulkDisableSelection();
    }

    /**
     * @param Language $object
     * @param string   $table
     *
     * @since 1.0.0
     */
    protected function copyFromPost(&$object, $table)
    {
        if ($object->id && ($object->iso_code != $_POST['iso_code'])) {
            if (Validate::isLanguageIsoCode($_POST['iso_code'])) {
                $object->moveToIso($_POST['iso_code']);
            }
        }
        parent::copyFromPost($object, $table);
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
        parent::afterImageUpload();

        if (($idLang = (int) Tools::getValue('id_lang')) && isset($_FILES) && count($_FILES) && file_exists(_PS_LANG_IMG_DIR_.$idLang.'.jpg')) {
            $currentFile = _PS_TMP_IMG_DIR_.'lang_mini_'.$idLang.'_'.$this->context->shop->id.'.jpg';

            if (file_exists($currentFile)) {
                unlink($currentFile);
            }
        }

        return true;
    }
}
