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
 * Class AdminSuppliersControllerCore
 *
 * @property Supplier|null $object
 */
class AdminSuppliersControllerCore extends AdminController
{
    /**
     * @var bool
     */
    public $bootstrap = true;

    /**
     * AdminSuppliersControllerCore constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->table = 'supplier';
        $this->className = 'Supplier';

        $this->addRowAction('view');
        $this->addRowAction('edit');
        $this->addRowAction('delete');
        $this->allow_export = true;

        $this->_defaultOrderBy = 'name';
        $this->_defaultOrderWay = 'ASC';

        $this->bulk_actions = [
            'delete' => [
                'text'    => $this->l('Delete selected'),
                'icon'    => 'icon-trash',
                'confirm' => $this->l('Delete selected items?'),
            ],
        ];

        $this->_select = 'COUNT(DISTINCT ps.`id_product`) AS products';
        $this->_join = 'LEFT JOIN `'._DB_PREFIX_.'product_supplier` ps ON (a.`id_supplier` = ps.`id_supplier`)';
        $this->_group = 'GROUP BY a.`id_supplier`';

        $this->fieldImageSettings = ['name' => 'logo', 'dir' => 'su'];

        $this->fields_list = [
            'id_supplier' => ['title' => $this->l('ID'), 'align' => 'center', 'class' => 'fixed-width-xs'],
            'logo'        => ['title' => $this->l('Logo'), 'align' => 'center', 'image' => 'su', 'orderby' => false, 'search' => false],
            'name'        => ['title' => $this->l('Name')],
            'products'    => ['title' => $this->l('Number of products'), 'align' => 'right', 'filter_type' => 'int', 'tmpTableFilter' => true],
            'active'      => ['title' => $this->l('Enabled'), 'align' => 'center', 'active' => 'status', 'type' => 'bool', 'orderby' => false, 'class' => 'fixed-width-xs'],
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
     * @return void
     *
     * @throws PrestaShopException
     */
    public function initPageHeaderToolbar()
    {
        if (empty($this->display)) {
            $this->page_header_toolbar_btn['new_supplier'] = [
                'href' => static::$currentIndex.'&addsupplier&token='.$this->token,
                'desc' => $this->l('Add new supplier', null, null, false),
                'icon' => 'process-icon-new',
            ];
        }

        parent::initPageHeaderToolbar();
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
        // loads current warehouse
        if (!($obj = $this->loadObject(true))) {
            return '';
        }

        $image = _PS_SUPP_IMG_DIR_.$obj->id.'.jpg';
        $imageUrl = ImageManager::thumbnail($image, $this->table.'_'.(int) $obj->id.'.'.$this->imageType, 350, $this->imageType, true, true);
        $imageSize = file_exists($image) ? filesize($image) / 1000 : false;

        $tmpAddr = new Address();
        $res = $tmpAddr->getFieldsRequiredDatabase();
        $requiredFields = [];
        foreach ($res as $row) {
            $requiredFields[(int) $row['id_required_field']] = $row['field_name'];
        }

        $this->fields_form = [
            'legend' => [
                'title' => $this->l('Suppliers'),
                'icon'  => 'icon-truck',
            ],
            'input'  => [
                [
                    'type' => 'hidden',
                    'name' => 'id_address',
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Name'),
                    'name'     => 'name',
                    'required' => true,
                    'col'      => 4,
                    'hint'     => $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                ],
                [
                    'type'      => in_array('company', $requiredFields) ? 'text' : 'hidden',
                    'label'     => $this->l('Company'),
                    'name'      => 'company',
                    'display'   => in_array('company', $requiredFields),
                    'required'  => in_array('company', $requiredFields),
                    'maxlength' => 16,
                    'col'       => 4,
                    'hint'      => $this->l('Company name for this supplier'),
                ],
                [
                    'type'         => 'textarea',
                    'label'        => $this->l('Description'),
                    'name'         => 'description',
                    'lang'         => true,
                    'hint'         => [
                        $this->l('Invalid characters:').' &lt;&gt;;=#{}',
                        $this->l('Will appear in the list of suppliers.'),
                    ],
                    'autoload_rte' => 'rte' //Enable TinyMCE editor for short description
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Phone'),
                    'name'      => 'phone',
                    'required'  => in_array('phone', $requiredFields),
                    'maxlength' => 16,
                    'col'       => 4,
                    'hint'      => $this->l('Phone number for this supplier'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Mobile phone'),
                    'name'      => 'phone_mobile',
                    'required'  => in_array('phone_mobile', $requiredFields),
                    'maxlength' => 16,
                    'col'       => 4,
                    'hint'      => $this->l('Mobile phone number for this supplier.'),
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Address'),
                    'name'      => 'address',
                    'maxlength' => 128,
                    'col'       => 6,
                    'required'  => true,
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Address').' (2)',
                    'name'      => 'address2',
                    'required'  => in_array('address2', $requiredFields),
                    'col'       => 6,
                    'maxlength' => 128,
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('Zip/postal code'),
                    'name'      => 'postcode',
                    'required'  => in_array('postcode', $requiredFields),
                    'maxlength' => 12,
                    'col'       => 2,
                ],
                [
                    'type'      => 'text',
                    'label'     => $this->l('City'),
                    'name'      => 'city',
                    'maxlength' => 32,
                    'col'       => 4,
                    'required'  => true,
                ],
                [
                    'type'          => 'select',
                    'label'         => $this->l('Country'),
                    'name'          => 'id_country',
                    'required'      => true,
                    'col'           => 4,
                    'default_value' => (int) $this->context->country->id,
                    'options'       => [
                        'query' => Country::getCountries($this->context->language->id, false),
                        'id'    => 'id_country',
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'    => 'select',
                    'label'   => $this->l('State'),
                    'name'    => 'id_state',
                    'col'     => 4,
                    'options' => [
                        'id'    => 'id_state',
                        'query' => [],
                        'name'  => 'name',
                    ],
                ],
                [
                    'type'          => 'file',
                    'label'         => $this->l('Logo'),
                    'name'          => 'logo',
                    'display_image' => true,
                    'image'         => $imageUrl ? $imageUrl : false,
                    'size'          => $imageSize,
                    'hint'          => $this->l('Upload a supplier logo from your computer.'),
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
                        $this->l('To add "tags" click in the field, write something and then press "Enter".'),
                        $this->l('Forbidden characters:').' &lt;&gt;;=#{}',
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
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        // loads current address for this supplier - if possible
        $address = null;
        if (isset($obj->id)) {
            $idAddress = Address::getAddressIdBySupplierId($obj->id);
            if ($idAddress > 0) {
                $address = new Address((int) $idAddress);
            }
        }

        // force specific fields values (address)
        if ($address != null) {
            $this->fields_value = [
                'id_address'   => $address->id,
                'phone'        => $address->phone,
                'phone_mobile' => $address->phone_mobile,
                'address'      => $address->address1,
                'address2'     => $address->address2,
                'postcode'     => $address->postcode,
                'city'         => $address->city,
                'id_country'   => $address->id_country,
                'id_state'     => $address->id_state,
            ];
        } else {
            $this->fields_value = [
                'id_address' => 0,
                'id_country' => Configuration::get('PS_COUNTRY_DEFAULT'),
            ];
        }

        if (Shop::isFeatureActive()) {
            $this->fields_form['input'][] = [
                'type'  => 'shop',
                'label' => $this->l('Shop association'),
                'name'  => 'checkBoxShopAsso',
            ];
        }

        return parent::renderForm();
    }

    /**
     * AdminController::initToolbar() override
     *
     * @throws PrestaShopException
     * @see AdminController::initToolbar()
     */
    public function initToolbar()
    {
        parent::initToolbar();

        if (empty($this->display) && $this->can_import) {
            $this->toolbar_btn['import'] = [
                'href' => $this->context->link->getAdminLink('AdminImport', true, ['import_type' => AdminImportController::ENTITY_TYPE_SUPPLIERS]),
                'desc' => $this->l('Import'),
            ];
        }
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
        $supplierId = (int)$this->object->id;
        $this->toolbar_title = $this->object->name;
        $languageId = $this->context->language->id;
        $link = $this->context->link;

        $products = [];
        foreach ($this->object->getProductsLite($languageId) as $row) {
            $productId = (int)$row['id_product'];
            $product = new Product($productId, false, $languageId);
            $product->loadStockData();
            $infos = Supplier::getProductInformationsBySupplier($supplierId, $productId, 0);
            $products[] = [
                'id' => (int)$product->id,
                'link' => $link->getAdminLink('AdminProducts', true, ['id_product' => $product->id, 'updateproduct' => true]),
                'name' => $product->name,
                'reference' => $product->reference,
                'ean13' => $product->ean13,
                'upc' => $product->upc,
                'quantity' => $product->quantity,
                'combinations' => $this->getProductCombinationArray($product, $languageId, $supplierId),
                'product_supplier_reference' => static::getSupplierReference($infos),
                'product_supplier_price_te' =>static::getSupplierPrice($infos),
            ];
        }


        $this->tpl_view_vars = [
            'supplier'         => $this->object,
            'products'         => $products,
            'stock_management' => Configuration::get('PS_STOCK_MANAGEMENT'),
            'shopContext'      => Shop::getContext(),
        ];

        return parent::renderView();
    }

    /**
     * @return false|Supplier
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processAdd()
    {
        if (Tools::isSubmit('id_supplier')) {
            return false;
        }

        $address = new Address();
        $address->alias = Tools::getValue('name', null);
        $address->lastname = 'supplier'; // skip problem with numeric characters in supplier name
        $address->firstname = 'supplier'; // skip problem with numeric characters in supplier name
        $address->address1 = Tools::getValue('address', null);
        $address->address2 = Tools::getValue('address2', null);
        $address->postcode = Tools::getValue('postcode', null);
        $address->phone = Tools::getValue('phone', null);
        $address->phone_mobile = Tools::getValue('phone_mobile', null);
        $address->id_country = Tools::getValue('id_country', null);
        $address->id_state = Tools::getValue('id_state', null);
        $address->city = Tools::getValue('city', null);

        $validation = $address->validateController();

        // checks address validity
        if (count($validation) > 0) {
            foreach ($validation as $item) {
                $this->errors[] = $item;
            }
            $this->errors[] = Tools::displayError('The address is not correct. Please make sure all of the required fields are completed.');
        } else {
            if (Tools::isSubmit('id_address') && Tools::getValue('id_address') > 0) {
                $address->update();
            } else {
                $address->save();
                $_POST['id_address'] = $address->id;
            }
        }

        if (Validate::isLoadedObject($address)) {
            /** @var Supplier|false $supplier */
            $supplier = parent::processAdd();
            return $supplier;
        }

        return false;
    }

    /**
     * @return false|Supplier
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function processUpdate()
    {
        if (Tools::isSubmit('id_supplier') && !($this->loadObject(true))) {
            return false;
        }

        // updates/creates address if it does not exist
        if (Tools::isSubmit('id_address') && (int) Tools::getValue('id_address') > 0) {
            $address = new Address((int) Tools::getValue('id_address'));
        } // updates address
        else {
            $address = new Address();
        }

        $address->alias = Tools::getValue('name', null);
        $address->lastname = 'supplier'; // skip problem with numeric characters in supplier name
        $address->firstname = 'supplier'; // skip problem with numeric characters in supplier name
        $address->address1 = Tools::getValue('address', null);
        $address->address2 = Tools::getValue('address2', null);
        $address->postcode = Tools::getValue('postcode', null);
        $address->phone = Tools::getValue('phone', null);
        $address->phone_mobile = Tools::getValue('phone_mobile', null);
        $address->id_country = Tools::getValue('id_country', null);
        $address->id_state = Tools::getValue('id_state', null);
        $address->city = Tools::getValue('city', null);

        $validation = $address->validateController();

        // checks address validity
        if (count($validation) > 0) {
            foreach ($validation as $item) {
                $this->errors[] = $item;
            }
            $this->errors[] = Tools::displayError('The address is not correct. Please make sure all of the required fields are completed.');
        } else {
            if (Tools::isSubmit('id_address') && Tools::getValue('id_address') > 0) {
                $address->update();
            } else {
                $address->save();
                $_POST['id_address'] = $address->id;
            }
        }

        if (Validate::isLoadedObject($address)) {
            /** @var Supplier|false $supplier */
            $supplier = parent::processUpdate();
            return $supplier;
        }

        return false;
    }

    /**
     * @return bool
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected function afterImageUpload()
    {
        $return = true;

        /* Generate image with differents size */
        if (($idSupplier = (int) Tools::getValue('id_supplier')) &&
            isset($_FILES) && count($_FILES) && file_exists(_PS_SUPP_IMG_DIR_.$idSupplier.'.jpg')
        ) {
            $imagesTypes = ImageType::getImagesTypes('suppliers');
            foreach ($imagesTypes as $imageType) {
                $file = _PS_SUPP_IMG_DIR_.$idSupplier.'.jpg';
                $return = ImageManager::resize(
                    $file,
                    _PS_SUPP_IMG_DIR_.$idSupplier.'-'.stripslashes($imageType['name']).'.jpg',
                    (int) $imageType['width'],
                    (int) $imageType['height']
                ) && $return;
                if (ImageManager::generateWebpImages()) {
                    $return = ImageManager::resize(
                        $file,
                        _PS_SUPP_IMG_DIR_.$idSupplier.'-'.stripslashes($imageType['name']).'.webp',
                        (int) $imageType['width'],
                        (int) $imageType['height'],
                        'webp'
                    ) && $return;
                }

                if (ImageManager::retinaSupport()) {
                    $return = ImageManager::resize(
                        $file,
                        _PS_SUPP_IMG_DIR_.$idSupplier.'-'.stripslashes($imageType['name']).'2x.jpg',
                        (int) $imageType['width'] * 2,
                        (int) $imageType['height'] * 2
                    ) && $return;
                    if (ImageManager::generateWebpImages()) {
                        $return = ImageManager::resize(
                            $file,
                            _PS_SUPP_IMG_DIR_.$idSupplier.'-'.stripslashes($imageType['name']).'2x.webp',
                            (int) $imageType['width'] * 2,
                            (int) $imageType['height'] * 2,
                            'webp'
                        ) && $return;
                    }
                }
            }

            $currentLogoFile = _PS_TMP_IMG_DIR_.'supplier_mini_'.$idSupplier.'_'.$this->context->shop->id.'.jpg';

            if (file_exists($currentLogoFile)) {
                unlink($currentLogoFile);
            }
        }

        if ($return) {
            if ((int) Configuration::get('TB_IMAGES_LAST_UPD_SUPPLIERS') < $idSupplier) {
                Configuration::updateValue('TB_IMAGES_LAST_UPD_SUPPLIERS', $idSupplier);
            }
        }

        return $return;
    }

    /**
     * @param Supplier $object
     *
     * @return bool
     * @throws PrestaShopException
     * @see AdminController::afterAdd()
     */
    protected function afterAdd($object)
    {
        $idAddress = (int) $_POST['id_address'];
        $address = new Address($idAddress);
        if (Validate::isLoadedObject($address)) {
            $address->id_supplier = $object->id;
            $address->save();
        }

        return true;
    }

    /**
     * @param Supplier $object
     *
     * @return bool
     * @throws PrestaShopException
     * @see AdminController::afterUpdate()
     */
    protected function afterUpdate($object)
    {
        $idAddress = (int) $_POST['id_address'];
        $address = new Address($idAddress);
        if (Validate::isLoadedObject($address)) {
            if ($address->id_supplier != $object->id) {
                $address->id_supplier = $object->id;
                $address->save();
            }
        }

        return true;
    }

    /**
     * @param Product $product
     * @param int $languageId
     * @param int $supplierId
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    protected static function getProductCombinationArray(Product $product, int $languageId, int $supplierId)
    {
        $combinations = [];
        foreach ($product->getAttributeCombinations($languageId) as $combination) {
            $combinationId = (int)$combination['id_product_attribute'];
            if (! isset($combinations[$combinationId])) {
                $infos = Supplier::getProductInformationsBySupplier($supplierId, $product->id, $combinationId);
                $combinations[$combinationId] = [
                    'id' => $combinationId,
                    'reference' => $combination['reference'],
                    'ean13' => $combination['ean13'],
                    'upc' => $combination['upc'],
                    'quantity' => $combination['quantity'],
                    'product_supplier_reference' => static::getSupplierReference($infos),
                    'product_supplier_price_te' =>static::getSupplierPrice($infos),
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

    /**
     * @param array|false $infos
     *
     * @return string
     */
    protected static function getSupplierReference($infos)
    {
        if ($infos && isset($infos['product_supplier_reference'])) {
            return (string)$infos['product_supplier_reference'];
        }
        return '';
    }

    /**
     * @param array|false $infos
     *
     * @return string
     *
     * @throws PrestaShopException
     */
    protected static function getSupplierPrice($infos)
    {
        if ($infos) {
            $price = (float)($infos['product_supplier_price_te'] ?? 0);
            $currencyId = (int)$infos['id_currency'];
        } else {
            $price = 0;
            $currencyId = 0;
        }
        if ($price == 0) {
            return '';
        }

        $currency = Currency::getCurrencyInstance($currencyId);
        if (! Validate::isLoadedObject($currencyId)) {
            $currency = Currency::getDefaultCurrency();
        }
        return Tools::displayPrice($price, $currency);
    }
}
