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

/**
 * Class AdminManufacturersControllerCore
 *
 * @property Manufacturer|null $object
 */
class AdminManufacturersControllerCore extends AdminController
{
    /** @var bool $bootstrap */
    public $bootstrap = true;
    /** @var array countries list */
    protected $countries_array = [];

    /**
     * AdminManufacturersControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->table = 'manufacturer';
        $this->className = 'Manufacturer';
        $this->lang = false;
        $this->deleted = false;
        $this->allow_export = true;
        $this->list_id = 'manufacturer';
        $this->identifier = 'id_manufacturer';
        $this->_defaultOrderBy = 'name';
        $this->_defaultOrderWay = 'ASC';

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'icon'    => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];

        $this->context = Context::getContext();

        $this->fieldImageSettings = [
            'name' => 'logo',
            'dir'  => 'm',
        ];

        $this->fields_list = [
            'id_manufacturer' => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'logo'            => [
                'title'   => $this->l('Logo'),
                'image'   => 'm',
                'orderby' => false,
                'search'  => false,
                'align'   => 'center',
            ],
            'name'            => [
                'title' => $this->l('Name'),
                'width' => 'auto',
            ],
            'addresses'       => [
                'title'  => $this->l('Addresses'),
                'search' => false,
                'align'  => 'center',
            ],
            'products'        => [
                'title'  => $this->l('Products'),
                'search' => false,
                'align'  => 'center',
            ],
            'active'          => [
                'title'   => $this->l('Enabled'),
                'active'  => 'status',
                'type'    => 'bool',
                'align'   => 'center',
                'class'   => 'fixed-width-xs',
                'orderby' => false,
            ],
        ];

        parent::__construct();
    }

    /**
     * Set media
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function setMedia()
    {
        parent::setMedia();
        $this->addJqueryUi('ui.widget');
        $this->addJqueryPlugin('tagify');
    }

    /**
     * @param string $textDelimiter
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function processExport($textDelimiter = '"')
    {
        if (strtolower($this->table) == 'address') {
            $this->_defaultOrderBy = 'id_manufacturer';
            $this->_where = 'AND a.`id_customer` = 0 AND a.`id_supplier` = 0 AND a.`id_warehouse` = 0 AND a.`deleted`= 0';
        }

        parent::processExport($textDelimiter);
    }

    /**
     * Display editaddresses action link
     *
     * @param string $token the token to add to the link
     * @param int $id the identifier to add to the link
     *
     * @return string
     *
     * @throws SmartyException
     */
    public function displayEditaddressesLink($token, $id)
    {
        if (!array_key_exists('editaddresses', static::$cache_lang)) {
            static::$cache_lang['editaddresses'] = $this->l('Edit');
        }

        $this->context->smarty->assign(
            [
                'href'   => static::$currentIndex.'&'.$this->identifier.'='.$id.'&editaddresses&token='.($token != null ? $token : $this->token),
                'action' => static::$cache_lang['editaddresses'],
            ]
        );

        return $this->context->smarty->fetch('helpers/list/list_action_edit.tpl');
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
        // toolbar (save, cancel, new, ..)
        $this->initToolbar();
        $this->initPageHeaderToolbar();
        if ($this->display == 'editaddresses' || $this->display == 'addaddress') {
            $this->renderFormAddress();
        } elseif ($this->display == 'edit' || $this->display == 'add') {
            if (!$this->loadObject(true)) {
                return;
            }
            $this->content .= $this->renderForm();
        } elseif ($this->display == 'view') {
            // Some controllers use the view action without an object
            if ($this->className) {
                $this->loadObject(true);
            }
            $this->content .= $this->renderView();
        } elseif (!$this->ajax) {
            $this->renderList();
            $this->content .= $this->renderOptions();
        }

        $this->context->smarty->assign(
            [
                'content'                   => $this->content,
                'url_post'                  => static::$currentIndex.'&token='.$this->token,
                'show_page_header_toolbar'  => $this->show_page_header_toolbar,
                'page_header_toolbar_title' => $this->page_header_toolbar_title,
                'page_header_toolbar_btn'   => $this->page_header_toolbar_btn,
            ]
        );
    }

    /**
     * AdminController::initToolbar() override
     *
     * @return void
     *
     * @throws PrestaShopException
     * @see AdminController::initToolbar()
     */
    public function initToolbar()
    {
        switch ($this->display) {
            case 'editaddresses':
            case 'addaddress':
                $this->toolbar_btn['save'] = [
                    'href' => '#',
                    'desc' => $this->l('Save'),
                ];

                $this->toolbar_btn['cancel'] = [
                    'href' => $this->getBackUrlParameter(),
                    'desc' => $this->l('Cancel'),
                ];
                break;

            default:
                parent::initToolbar();

                if ($this->can_import) {
                    $this->toolbar_btn['import'] = [
                        'href' => $this->context->link->getAdminLink('AdminImport', true, ['import_type' => AdminImportController::ENTITY_TYPE_MANUFACTURERS]),
                        'desc' => $this->l('Import'),
                    ];
                }
        }
    }

    /**
     * Init page header
     *
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_manufacturer'] = [
                'href' => static::$currentIndex.'&addmanufacturer&token='.$this->token,
                'desc' => $this->l('Add new manufacturer', null, null, false),
                'icon' => 'process-icon-new',
            ];
            $this->page_header_toolbar_btn['new_manufacturer_address'] = [
                'href' => static::$currentIndex.'&addaddress&token='.$this->token,
                'desc' => $this->l('Add new manufacturer address', null, null, false),
                'icon' => 'process-icon-new',
            ];
        } elseif ($this->display == 'editaddresses' || $this->display == 'addaddress') {
            $this->page_header_toolbar_btn['cancel'] = [
                'href' => $this->getBackUrlParameter(),
                'desc' => $this->l('Cancel', null, null, false),
            ];
        }

        parent::initPageHeaderToolbar();
    }

    /**
     * Render address form
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderFormAddress()
    {
        // Change table and className for addresses
        $this->table = 'address';
        $this->className = 'Address';
        $idAddress = Tools::getIntValue('id_address');

        // Create Object Address
        $address = new Address($idAddress);

        $res = $address->getFieldsRequiredDatabase();
        $requiredFields = [];
        foreach ($res as $row) {
            $requiredFields[(int) $row['id_required_field']] = $row['field_name'];
        }

        $form = [
            'legend' => [
                'title' => $this->l('Addresses'),
                'icon'  => 'icon-building',
            ],
        ];

        if (!$address->id_manufacturer || !Manufacturer::manufacturerExists($address->id_manufacturer)) {
            $form['input'][] = [
                'type'    => 'select',
                'label'   => $this->l('Choose the manufacturer'),
                'name'    => 'id_manufacturer',
                'options' => [
                    'query' => Manufacturer::getManufacturers(),
                    'id'    => 'id_manufacturer',
                    'name'  => 'name',
                ],
            ];
        } else {
            $form['input'][] = [
                'type'     => 'text',
                'label'    => $this->l('Manufacturer'),
                'name'     => 'name',
                'col'      => 4,
                'disabled' => true,
            ];

            $form['input'][] = [
                'type' => 'hidden',
                'name' => 'id_manufacturer',
            ];
        }

        $form['input'][] = [
            'type' => 'hidden',
            'name' => 'alias',
        ];

        $form['input'][] = [
            'type' => 'hidden',
            'name' => 'id_address',
        ];

        if (in_array('company', $requiredFields)) {
            $form['input'][] = [
                'type'      => 'text',
                'label'     => $this->l('Company'),
                'name'      => 'company',
                'display'   => in_array('company', $requiredFields),
                'required'  => in_array('company', $requiredFields),
                'maxlength' => 16,
                'col'       => 4,
                'hint'      => $this->l('Company name for this supplier'),
            ];
        }

        $form['input'][] = [
            'type'     => 'text',
            'label'    => $this->l('Last name'),
            'name'     => 'lastname',
            'required' => true,
            'col'      => 4,
            'hint'     => $this->l('Invalid characters:').' 0-9!&lt;&gt;,;?=+()@#"�{}_$%:',
        ];
        $form['input'][] = [
            'type'     => 'text',
            'label'    => $this->l('First name'),
            'name'     => 'firstname',
            'required' => true,
            'col'      => 4,
            'hint'     => $this->l('Invalid characters:').' 0-9!&lt;&gt;,;?=+()@#"�{}_$%:',
        ];
        $form['input'][] = [
            'type'     => 'text',
            'label'    => $this->l('Address'),
            'name'     => 'address1',
            'col'      => 6,
            'required' => true,
        ];
        $form['input'][] = [
            'type'     => 'text',
            'label'    => $this->l('Address (2)'),
            'name'     => 'address2',
            'col'      => 6,
            'required' => in_array('address2', $requiredFields),
        ];
        $form['input'][] = [
            'type'     => 'text',
            'label'    => $this->l('Zip/postal code'),
            'name'     => 'postcode',
            'col'      => 2,
            'required' => in_array('postcode', $requiredFields),
        ];
        $form['input'][] = [
            'type'     => 'text',
            'label'    => $this->l('City'),
            'name'     => 'city',
            'col'      => 4,
            'required' => true,
        ];
        $form['input'][] = [
            'type'          => 'select',
            'label'         => $this->l('Country'),
            'name'          => 'id_country',
            'required'      => false,
            'default_value' => (int) $this->context->country->id,
            'col'           => 4,
            'options'       => [
                'query' => Country::getCountries($this->context->language->id),
                'id'    => 'id_country',
                'name'  => 'name',
            ],
        ];
        $form['input'][] = [
            'type'     => 'select',
            'label'    => $this->l('State'),
            'name'     => 'id_state',
            'required' => false,
            'col'      => 4,
            'options'  => [
                'query' => [],
                'id'    => 'id_state',
                'name'  => 'name',
            ],
        ];
        $form['input'][] = [
            'type'     => 'text',
            'label'    => $this->l('Home phone'),
            'name'     => 'phone',
            'col'      => 4,
            'required' => in_array('phone', $requiredFields),
        ];
        $form['input'][] = [
            'type'     => 'text',
            'label'    => $this->l('Mobile phone'),
            'name'     => 'phone_mobile',
            'col'      => 4,
            'required' => in_array('phone_mobile', $requiredFields),
        ];
        $form['input'][] = [
            'type'     => 'textarea',
            'label'    => $this->l('Other'),
            'name'     => 'other',
            'required' => false,
            'hint'     => $this->l('Forbidden characters:').' &lt;&gt;;=#{}',
            'rows'     => 2,
            'cols'     => 10,
            'col'      => 6,
        ];
        $form['submit'] = [
            'title' => $this->l('Save'),
        ];

        $this->fields_value = [
            'name'       => Manufacturer::getNameById($address->id_manufacturer),
            'alias'      => 'manufacturer',
            'id_country' => $address->id_country,
        ];

        $this->initToolbar();
        $this->fields_form[0]['form'] = $form;
        $helper = new HelperForm();
        $helper->show_cancel_button = true;
        $helper->back_url = $this->getBackUrlParameter();
        $helper->currentIndex = static::$currentIndex;
        $helper->token = $this->token;
        $helper->table = $this->table;
        $helper->identifier = $this->identifier;
        $helper->title = $this->l('Edit Addresses');
        $helper->id = $address->id;
        $helper->toolbar_scroll = true;
        $helper->languages = $this->getLanguages();
        $helper->default_form_language = $this->getDefaultFormLanguage();
        $helper->allow_employee_form_lang = $this->getAllowEmployeeFormLanguage();
        $helper->fields_value = $this->getFieldsValue($address);
        $helper->toolbar_btn = $this->toolbar_btn;
        $this->content .= $helper->generateForm($this->fields_form);
    }

    /**
     * Render form
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderForm()
    {
        if (!($manufacturer = $this->loadObject(true))) {
            return '';
        }

        $image = _PS_MANU_IMG_DIR_.$manufacturer->id.'.jpg';
        $imageUrl = ImageManager::thumbnail(
            $image,
            $this->table.'_'.(int) $manufacturer->id.'.'.$this->imageType,
            350,
            $this->imageType,
            true,
            true
        );
        $imageSize = file_exists($image) ? filesize($image) / 1000 : false;

        $this->fields_form = [
            'tinymce' => true,
            'legend'  => [
                'title' => $this->l('Manufacturers'),
                'icon'  => 'icon-certificate',
            ],
            'input'   => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'col'      => 4,
                    'required' => true,
                    'hint'     => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Short description'),
                    'name'         => 'short_description',
                    'lang'         => true,
                    'cols'         => 60,
                    'rows'         => 10,
                    'autoload_rte' => 'rte', //Enable TinyMCE editor for short description
                    'col'          => 6,
                    'hint'         => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description'),
                    'name'         => 'description',
                    'lang'         => true,
                    'cols'         => 60,
                    'rows'         => 10,
                    'col'          => 6,
                    'autoload_rte' => 'rte', //Enable TinyMCE editor for description
                    'hint'         => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Logo'),
                    'name'          => 'logo',
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'display_image' => true,
                    'col'           => 6,
                    'hint'          => $this->l('Upload a manufacturer logo from your computer.'),
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta title'),
                    'name'  => 'meta_title',
                    'lang'  => true,
                    'col'   => 4,
                    'hint'  => $this->l('Forbidden characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'text',
                    'label' => $this->l('Meta description'),
                    'name'  => 'meta_description',
                    'lang'  => true,
                    'col'   => 6,
                    'hint'  => $this->l('Forbidden characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'  => 'tags',
                    'label' => $this->l('Meta keywords'),
                    'name'  => 'meta_keywords',
                    'lang'  => true,
                    'col'   => 6,
                    'hint'  => [
                        $this->l('Forbidden characters:').' &lt;&gt;;=#{}',
                        $this->l('To add "tags," click inside the field, write something, and then press "Enter."'),
                    ],
                ],
                [
                    'type'     => 'switch',
                    'label'    => $this->l('Enable'),
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

        return parent::renderForm();
    }

    /**
     * Render view
     *
     * @return string
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderView()
    {
        /** @var Manufacturer $manufacturer */
        if (!($manufacturer = $this->loadObject())) {
            return '';
        }

        $this->toolbar_btn['new'] = [
            'href' => $this->context->link->getAdminLink('AdminManufacturers').'&addaddress=1&id_manufacturer='.(int) $manufacturer->id,
            'desc' => $this->l('Add address'),
        ];

        $this->toolbar_title = is_array($this->breadcrumbs) ? array_unique($this->breadcrumbs) : [$this->breadcrumbs];
        $this->toolbar_title[] = $manufacturer->name;

        $link = $this->context->link;
        $languageId = (int)$this->context->language->id;
        $addresses = $manufacturer->getAddresses($languageId);

        $products = [];
        foreach ($manufacturer->getProductsLite($languageId) as $row) {
            $productId = (int)$row['id_product'];
            $product = new Product($productId, false, $languageId);
            $product->loadStockData();

            $products[] = [
                'id' => (int)$product->id,
                'link' => $link->getAdminLink('AdminProducts', true, ['id_product' => $product->id, 'updateproduct' => true]),
                'deleteLink' => $link->getAdminLink('AdminProducts', true, ['id_product' => $product->id, 'deleteproduct' => true]),
                'name' => $product->name,
                'reference' => $product->reference,
                'ean13' => $product->ean13,
                'upc' => $product->upc,
                'quantity' => $product->quantity,
                'combinations' => $this->getProductCombinationArray($product, $languageId),
            ];
        }

        $this->tpl_view_vars = [
            'manufacturer'     => $manufacturer,
            'addresses'        => $addresses,
            'products'         => $products,
            'stock_management' => Configuration::get('PS_STOCK_MANAGEMENT'),
            'shopContext'      => Shop::getContext(),
        ];

        return parent::renderView();
    }

    /**
     * Render list
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function renderList()
    {
        $this->initListManufacturer();
        $this->initListManufacturerAddresses();
    }

    /**
     * Init manufacturer list
     *
     * @return void
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initListManufacturer()
    {
        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_select = '
			COUNT(`id_product`) AS `products`, (
				SELECT COUNT(ad.`id_manufacturer`) as `addresses`
				FROM `'._DB_PREFIX_.'address` ad
				WHERE ad.`id_manufacturer` = a.`id_manufacturer`
					AND ad.`deleted` = 0
				GROUP BY ad.`id_manufacturer`) as `addresses`';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'product` p ON (a.`id_manufacturer` = p.`id_manufacturer`)';
        $this->_group = 'GROUP BY a.`id_manufacturer`';

        $this->context->smarty->assign('title_list', $this->l('List of manufacturers'));

        $this->content .= parent::renderList();
    }

    /**
     * Init manufacturer address list
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function initListManufacturerAddresses()
    {
        $this->toolbar_title = $this->l('Addresses');
        // reset actions and query vars
        $this->actions = [];
        unset($this->fields_list, $this->_select, $this->_join, $this->_group, $this->_filterHaving, $this->_filter);

        $this->table = 'address';
        $this->list_id = 'address';
        $this->identifier = 'id_address';
        $this->deleted = true;

        $this->_defaultOrderBy = 'id_address';
        $this->_defaultOrderWay = 'ASC';

        $this->_orderBy = null;

        $this->addRowAction('editaddresses');
        $this->addRowAction('delete');

        // test if a filter is applied for this list
        if (Tools::isSubmit('submitFilter'.$this->table) || $this->context->cookie->{'submitFilter'.$this->table} !== false) {
            $this->filter = true;
        }

        // test if a filter reset request is required for this list
        $this->action = (isset($_POST['submitReset'.$this->table]) ? 'reset_filters' : '');

        $this->fields_list = $this->getAddressFieldsList();
        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'icon'    => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];

        $this->_select = 'cl.`name` as country, m.`name` AS manufacturer_name';
        $this->_join = '
			LEFT JOIN `'._DB_PREFIX_.'country_lang` cl
				ON (cl.`id_country` = a.`id_country` AND cl.`id_lang` = '.(int) $this->context->language->id.') ';
        $this->_join .= '
			INNER JOIN `'._DB_PREFIX_.'manufacturer` m
				ON (a.`id_manufacturer` = m.`id_manufacturer`)';
        $this->_where = 'AND a.`deleted`= 0';

        $this->context->smarty->assign('title_list', $this->l('Manufacturers addresses'));

        // call postProcess() for take care about actions and filters
        $this->postProcess();

        $this->initToolbar();

        $this->content .= parent::renderList();
    }

    /**
     * Get address field list
     *
     * @return array
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function getAddressFieldsList()
    {
        // Sub tab addresses
        $countries = Country::getCountries($this->context->language->id);
        foreach ($countries as $country) {
            $this->countries_array[$country['id_country']] = $country['name'];
        }

        return [
            'id_address'        => [
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'manufacturer_name' => [
                'title'      => $this->l('Manufacturer'),
                'width'      => 'auto',
                'filter_key' => 'manufacturer_name',
            ],
            'firstname'         => [
                'title' => $this->l('First name'),
            ],
            'lastname'          => [
                'title'      => $this->l('Last name'),
                'filter_key' => 'a!lastname',
            ],
            'postcode'          => [
                'title' => $this->l('Zip/Postal code'),
                'align' => 'right',
            ],
            'city'              => [
                'title' => $this->l('City'),
            ],
            'country'           => [
                'title'      => $this->l('Country'),
                'type'       => 'select',
                'list'       => $this->countries_array,
                'filter_key' => 'cl!id_country',
            ],
        ];
    }

    /**
     * AdminController::init() override
     *
     * @return void
     *
     * @throws PrestaShopException
     * @see AdminController::init()
     */
    public function init()
    {
        parent::init();

        if (Tools::isSubmit('editaddresses')) {
            $this->display = 'editaddresses';
        } elseif (Tools::isSubmit('updateaddress')) {
            $this->display = 'editaddresses';
        } elseif (Tools::isSubmit('addaddress')) {
            $this->display = 'addaddress';
        } elseif (Tools::isSubmit('submitAddaddress')) {
            $this->action = 'save';
        } elseif (Tools::isSubmit('deleteaddress')) {
            $this->action = 'delete';
        }
    }

    /**
     * Init process
     *
     * @return void
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function initProcess()
    {
        if (Tools::isSubmit('submitAddaddress') || Tools::isSubmit('deleteaddress') || Tools::isSubmit('submitBulkdeleteaddress') || Tools::isSubmit('exportaddress')) {
            $this->table = 'address';
            $this->className = 'Address';
            $this->identifier = 'id_address';
            $this->deleted = true;
            $this->fields_list = $this->getAddressFieldsList();
        }
        parent::initProcess();
    }

    /**
     * Process save
     *
     * @return bool Indicates whether save was successful
     *
     * @throws PrestaShopException
     */
    public function processSave()
    {
        if (Tools::isSubmit('submitAddaddress')) {
            $this->display = 'editaddresses';
        }

        return parent::processSave();
    }

    /**
     * After image upload
     *
     * @return bool Indicates whether post processing was successful
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function afterImageUpload()
    {
        $res = true;

        /* Generate image with differents size */
        if (($idManufacturer = Tools::getIntValue('id_manufacturer')) &&
            $_FILES &&
            file_exists(_PS_MANU_IMG_DIR_.$idManufacturer.'.jpg')
        ) {
            $imagesTypes = ImageType::getImagesTypes('manufacturers');
            foreach ($imagesTypes as $imageType) {
                $res = ImageManager::resize(
                    _PS_MANU_IMG_DIR_.$idManufacturer.'.jpg',
                    _PS_MANU_IMG_DIR_.$idManufacturer.'-'.stripslashes($imageType['name']).'.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                ) && $res;
                if (ImageManager::generateWebpImages()) {
                    $res = ImageManager::resize(
                        _PS_MANU_IMG_DIR_.$idManufacturer.'.jpg',
                        _PS_MANU_IMG_DIR_.$idManufacturer.'-'.stripslashes($imageType['name']).'.webp',
                        (int) $imageType['width'],
                        (int) $imageType['height'],
                        'webp'
                    ) && $res;
                }
                if (ImageManager::retinaSupport()) {
                    $res = ImageManager::resize(
                        _PS_MANU_IMG_DIR_.$idManufacturer.'.jpg',
                        _PS_MANU_IMG_DIR_.$idManufacturer.'-'.stripslashes($imageType['name']).'2x.jpg',
                        (int) $imageType['width'] * 2,
                        (int) $imageType['height'] * 2
                    ) && $res;
                    if (ImageManager::generateWebpImages()) {
                        $res = ImageManager::resize(
                            _PS_MANU_IMG_DIR_.$idManufacturer.'.jpg',
                            _PS_MANU_IMG_DIR_.$idManufacturer.'-'.stripslashes($imageType['name']).'2x.webp',
                            (int) $imageType['width'] * 2,
                            (int) $imageType['height'] * 2,
                            'webp'
                        ) && $res;
                    }
                }
            }

            $currentLogoFile = _PS_TMP_IMG_DIR_.'manufacturer_mini_'.$idManufacturer.'_'.$this->context->shop->id.'.jpg';

            if ($res && file_exists($currentLogoFile)) {
                unlink($currentLogoFile);
            }
        }

        if (!$res) {
            $this->errors[] = Tools::displayError('Unable to resize one or more of your pictures.');
        } else {
            if ((int) Configuration::get('TB_IMAGES_LAST_UPD_MANUFACTURERS') < $idManufacturer) {
                Configuration::updateValue('TB_IMAGES_LAST_UPD_MANUFACTURERS', $idManufacturer);
            }
        }

        return $res;
    }

    /**
     * Before delete
     *
     * @param ObjectModel $object
     *
     * @return true
     */
    protected function beforeDelete($object)
    {
        return true;
    }

    /**
     * @param Product $product
     * @param int $languageId
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function getProductCombinationArray(Product $product, int $languageId)
    {
        $combinations = [];
        foreach ($product->getAttributeCombinations($languageId) as $combination) {
            $combinationId = (int)$combination['id_product_attribute'];
            if (! isset($combinations[$combinationId])) {
                $combinations[$combinationId] = [
                    'id' => $combinationId,
                    'reference' => $combination['reference'],
                    'ean13' => $combination['ean13'],
                    'upc' => $combination['upc'],
                    'quantity' => $combination['quantity'],
                    'attributes' => []
                ];
            }
            $combinations[$combinationId]['attributes'][] = $combination['group_name'] . ' - ' . $combination['attribute_name'];
        }
        foreach ($combinations as &$combination) {
            $combination['attributes'] = implode(', ', $combination['attributes']);
        }
        return $combinations;
    }
}
