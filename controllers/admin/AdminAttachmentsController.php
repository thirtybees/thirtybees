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
 * Class AdminAttachmentsControllerCore
 *
 * @since 1.0.0
 */
class AdminAttachmentsControllerCore extends AdminController
{
    // @codingStandardsIgnoreStart
    public $bootstrap = true;

    protected $product_attachements = [];
    // @codingStandardsIgnoreEnd

    /**
     * AdminAttachmentsControllerCore constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->table = 'attachment';
        $this->className = 'Attachment';
        $this->lang = true;

        $this->addRowAction('edit');
        $this->addRowAction('view');
        $this->addRowAction('delete');

        $this->_select = 'IFNULL(virtual_product_attachment.products, 0) as products';
        $this->_join = 'LEFT JOIN (SELECT id_attachment, COUNT(*) as products FROM '._DB_PREFIX_.'product_attachment GROUP BY id_attachment) AS virtual_product_attachment ON a.id_attachment = virtual_product_attachment.id_attachment';
        $this->_use_found_rows = false;

        $this->fields_list = [
            'id_attachment' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name'          => [
                'title' => $this->l('Name'),
            ],
            'file'          => [
                'title' => $this->l('File'),
            ],
            'file_size'     => [
                'title'    => $this->l('Size'),
                'callback' => 'displayHumanReadableSize',
            ],
            'products'      => [
                'title'      => $this->l('Associated with'),
                'suffix'     => $this->l('product(s)'),
                'filter_key' => 'virtual_product_attachment!products',
            ],
        ];

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'icon'    => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];

        parent::__construct();
    }

    /**
     * @param $size
     *
     * @return string
     *
     * @since 1.0.0
     */
    public static function displayHumanReadableSize($size)
    {
        return Tools::formatBytes($size);
    }

    /**
     * @since 1.0.0
     */
    public function setMedia()
    {
        parent::setMedia();

        $this->addJs(_PS_JS_DIR_.'/admin/attachments.js');
        Media::addJsDefL('confirm_text', $this->l('This attachment is associated with the following products, do you really want to  delete it?', null, true, false));
    }

    /**
     * @since 1.0.0
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_attachment'] = [
                'href' => static::$currentIndex.'&addattachment&token='.$this->token,
                'desc' => $this->l('Add new attachment', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * @since 1.0.0
     */
    public function renderView()
    {
        if (($obj = $this->loadObject(true)) && Validate::isLoadedObject($obj)) {
            $link = $this->context->link->getPageLink('attachment', true, null, 'id_attachment='.$obj->id);
            Tools::redirectLink($link);
        }

        return $this->displayWarning($this->l('File not found'));
    }

    /**
     * @return string
     *
     * @since 1.0.0
     */
    public function renderForm()
    {
        if (($obj = $this->loadObject(true)) && Validate::isLoadedObject($obj)) {
            /** @var Attachment $obj */
            $link = $this->context->link->getPageLink('attachment', true, null, 'id_attachment='.$obj->id);

            if (file_exists(_PS_DOWNLOAD_DIR_.$obj->file)) {
                $size = round(filesize(_PS_DOWNLOAD_DIR_.$obj->file) / 1024);
            }
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Attachment'),
                'icon'  => 'icon-paper-clip',
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Filename'),
                    'name'     => 'name',
                    'required' => true,
                    'lang'     => true,
                    'col'      => 4,
                ],
                [
                    'type'  => 'textarea',
                    'label' => $this->l('Description'),
                    'name'  => 'description',
                    'lang'  => true,
                    'col'   => 6,
                ],
                [
                    'type'     => 'file',
                    'file'     => isset($link) ? $link : null,
                    'size'     => isset($size) ? $size : null,
                    'label'    => $this->l('File'),
                    'name'     => 'file',
                    'required' => true,
                    'col'      => 6,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        return parent::renderForm();
    }

    /**
     * @param int  $idLang
     * @param null $orderBy
     * @param null $orderWay
     * @param int  $start
     * @param null $limit
     * @param bool $idLangShop
     *
     * @since 1.0.0
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $limit = null, $idLangShop = false)
    {
        parent::getList((int) $idLang, $orderBy, $orderWay, $start, $limit, $idLangShop);

        if (count($this->_list)) {
            $this->product_attachements = Attachment::getProductAttached((int) $idLang, $this->_list);

            $listProductList = [];
            foreach ($this->_list as $list) {
                $productList = '';

                if (isset($this->product_attachements[$list['id_attachment']])) {
                    foreach ($this->product_attachements[$list['id_attachment']] as $product) {
                        $productList .= $product.', ';
                    }

                    $productList = rtrim($productList, ', ');
                }

                $listProductList[$list['id_attachment']] = $productList;
            }

            // Assign array in list_action_delete.tpl
            $this->tpl_delete_link_vars = [
                'product_list'         => $listProductList,
                'product_attachements' => $this->product_attachements,
            ];
        }
    }

    /**
     * @return bool|null
     *
     * @since 1.0.0
     */
    public function postProcess()
    {
        if (_PS_MODE_DEMO_) {
            $this->errors[] = Tools::displayError('This functionality has been disabled.');

            return null;
        }

        if (Tools::isSubmit('submitAdd'.$this->table)) {
            $id = (int) Tools::getValue('id_attachment');
            if ($id && $a = new Attachment($id)) {
                $_POST['file'] = $a->file;
                $_POST['mime'] = $a->mime;
            }
            if (!count($this->errors)) {
                if (isset($_FILES['file']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                    if ($_FILES['file']['size'] > (Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE') * 1024 * 1024)) {
                        $this->errors[] = sprintf(
                            $this->l('The file is too large. Maximum size allowed is: %1$d kB. The file you are trying to upload is %2$d kB.'),
                            (Configuration::get('PS_ATTACHMENT_MAXIMUM_SIZE') * 1024),
                            number_format(($_FILES['file']['size'] / 1024), 2, '.', '')
                        );
                    } else {
                        do {
                            $uniqid = sha1(microtime());
                        } while (file_exists(_PS_DOWNLOAD_DIR_.$uniqid));
                        if (!move_uploaded_file($_FILES['file']['tmp_name'], _PS_DOWNLOAD_DIR_.$uniqid)) {
                            $this->errors[] = $this->l('Failed to copy the file.');
                        }
                        $_POST['file_name'] = $_FILES['file']['name'];
                        @unlink($_FILES['file']['tmp_name']);
                        if (!sizeof($this->errors) && isset($a) && file_exists(_PS_DOWNLOAD_DIR_.$a->file)) {
                            unlink(_PS_DOWNLOAD_DIR_.$a->file);
                        }
                        $_POST['file'] = $uniqid;
                        $_POST['mime'] = $_FILES['file']['type'];
                    }
                } elseif (array_key_exists('file', $_FILES) && (int) $_FILES['file']['error'] === 1) {
                    $maxUpload = (int) ini_get('upload_max_filesize');
                    $maxPost = (int) ini_get('post_max_size');
                    $uploadMb = min($maxUpload, $maxPost);
                    $this->errors[] = sprintf(
                        $this->l('The file %1$s exceeds the size allowed by the server. The limit is set to %2$d MB.'),
                        '<b>'.$_FILES['file']['name'].'</b> ',
                        '<b>'.$uploadMb.'</b>'
                    );
                } elseif (!isset($a) || (isset($a) && !file_exists(_PS_DOWNLOAD_DIR_.$a->file))) {
                    $this->errors[] = $this->l('Upload error. Please check your server configurations for the maximum upload size allowed.');
                }
            }
            $this->validateRules();
        }
        $return = parent::postProcess();
        if (!$return && isset($uniqid) && file_exists(_PS_DOWNLOAD_DIR_.$uniqid)) {
            unlink(_PS_DOWNLOAD_DIR_.$uniqid);
        }

        return $return;
    }
}
